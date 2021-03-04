const baseApi =
  (process.env.VUE_APP_API_SERVER || '') + '/rest/site/fe/matter/mission'

export default function create(tmsAxios) {
  return {
    entryRule(siteId, missionId) {
      const params = { site: siteId, mission: missionId }
      return tmsAxios.get(`${baseApi}/entryRule`, { params }).then((rst) => {
        return rst.data.data
      })
    },
    get(siteId, missionId) {
      const params = { site: siteId, mission: missionId }
      return tmsAxios.get(`${baseApi}/get`, { params }).then((rst) => {
        return rst.data.data
      })
    },
    userTrack(siteId, missionId) {
      const params = { site: siteId, mission: missionId }
      return tmsAxios.get(`${baseApi}/userTrack`, { params }).then((rst) => {
        return rst.data.data
      })
    },
    docList(siteId, missionId, matterType, channelId) {
      const params = { site: siteId, mission: missionId, channel: channelId }
      params.matterType = Array.isArray(matterType)
        ? matterType.join(',')
        : matterType
      return tmsAxios
        .get(`${baseApi}/matter/docList`, { params })
        .then((rst) => {
          return rst.data.data
        })
    },
    channelList(siteId, missionId) {
      return this.docList(siteId, missionId, 'channel')
    },
  }
}
